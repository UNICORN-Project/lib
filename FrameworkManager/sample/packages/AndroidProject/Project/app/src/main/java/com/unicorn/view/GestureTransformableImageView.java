package com.unicorn.view;

import com.unicorn.view.DragGestureDetector;
import com.unicorn.view.DragGestureDetector.DragGestureListener;
import com.unicorn.view.PinchGestureDetector;
import com.unicorn.view.PinchGestureDetector.PinchGestureListener;
import com.unicorn.view.RotateGestureDetector;
import com.unicorn.view.RotateGestureDetector.RotateGestureListener;
import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.Canvas;
import android.graphics.PointF;
import android.graphics.Rect;
import android.util.AttributeSet;
import android.view.MotionEvent;
import android.view.View;
import android.view.View.OnTouchListener;
import android.widget.ImageView;

public class GestureTransformableImageView extends ImageView implements OnTouchListener {

	public static final int GESTURE_DRAGGABLE = 0x0001;

	public static final int GESTURE_ROTATABLE = 0x0002;

	public static final int GESTURE_SCALABLE = 0x0004;

	public static final String TAG = GestureTransformableImageView.class.getName();

	public static final float DEFAULT_LIMIT_SCALE_MAX = 2.7f;

	public static final float DEFAULT_LIMIT_SCALE_MIN = 0.5f;

	private float limitScaleMax = DEFAULT_LIMIT_SCALE_MAX;

	private float limitScaleMin = DEFAULT_LIMIT_SCALE_MIN;

	private float scaleFactor = 1.0f;

	private RotateGestureDetector rotateGestureDetector;

	private DragGestureDetector dragGestureDetector;

	private PinchGestureDetector pinchGestureDetector;

	private float angle;

	public GestureTransformableImageView(Context context, AttributeSet attrs, int defStyle) {
		super(context, attrs, defStyle);
		init(context, GESTURE_DRAGGABLE | GESTURE_ROTATABLE | GESTURE_SCALABLE);
	}

	public GestureTransformableImageView(Context context, AttributeSet attrs) {
		super(context, attrs);
		init(context, GESTURE_DRAGGABLE | GESTURE_ROTATABLE | GESTURE_SCALABLE);
	}

	public GestureTransformableImageView(Context context) {
		super(context);
		init(context, GESTURE_DRAGGABLE | GESTURE_ROTATABLE | GESTURE_SCALABLE);
	}

	public GestureTransformableImageView(Context context, int gestureFlag) {
		super(context);
		init(context, gestureFlag);
	}

	public void setLimitScaleMax(float limit) {
		this.limitScaleMax = limit;
	}

	public void setLimitScaleMin(float limit) {
		this.limitScaleMin = limit;
	}

	@Override
	public boolean onTouch(View v, MotionEvent event) {

		if (rotateGestureDetector != null) {
			rotateGestureDetector.onTouchEvent(event);
		}

		if (dragGestureDetector != null) {
			dragGestureDetector.onTouchEvent(event);
		}

		if (pinchGestureDetector != null) {
			pinchGestureDetector.onTouchEvent(event);
		}

		return true;
	}

	public void setScale(float scale) {
		scaleFactor = scale;
		setScaleX(scaleFactor);
		setScaleY(scaleFactor);
	}

	public void setTouchEnable() {
		setOnTouchListener(null);
	}

	private void init(Context context, int gestureFlag) {

		setOnTouchListener(this);

		if ((gestureFlag & GESTURE_DRAGGABLE) == GESTURE_DRAGGABLE) {
			dragGestureDetector = new DragGestureDetector(new DragListener());
		}
		if ((gestureFlag & GESTURE_ROTATABLE) == GESTURE_ROTATABLE) {
			rotateGestureDetector = new RotateGestureDetector(new RotateListener());
		}
		if ((gestureFlag & GESTURE_SCALABLE) == GESTURE_SCALABLE) {
			pinchGestureDetector = new PinchGestureDetector(new ScaleListener());
		}

	}

	private PointF rotateXY(float centerX, float centerY, float angle, float x, float y) {

		float resultX = 0;
		float resultY = 0;

		double rad = Math.toRadians(angle);

		resultX = (float) ((x - centerX) * Math.cos(rad) - (y - centerY) * Math.sin(rad) + centerX);
		resultY = (float) ((x - centerX) * Math.sin(rad) + (y - centerY) * Math.cos(rad) + centerY);

		return new PointF(resultX, resultY);
	}

	private class ScaleListener implements PinchGestureListener {

		@Override
		public void onPinchGestureListener(PinchGestureDetector dragGestureDetector) {

			float scale = dragGestureDetector.getDistance() / dragGestureDetector.getPreDistance();
			float tmpScale = scaleFactor * scale;

			if (limitScaleMin <= tmpScale && tmpScale <= limitScaleMax) {
				scaleFactor = tmpScale;
				setScaleX(scaleFactor);
				setScaleY(scaleFactor);

				return;
			}

		}
	}

	private class RotateListener implements RotateGestureListener {

		@Override
		public void onRotation(RotateGestureDetector detector) {

			angle += detector.getDeltaAngle();

			setRotation(getRotation() + detector.getDeltaAngle());
		}

		@Override
		public void onRotationBegin(RotateGestureDetector detector) {

		}

		@Override
		public void onRotationEnd(RotateGestureDetector detector) {
		}
	}

	private class DragListener implements DragGestureListener {

		@Override
		synchronized public void onDragGestureListener(DragGestureDetector dragGestureDetector) {

			float dx = dragGestureDetector.getDeltaX();
			float dy = dragGestureDetector.getDeltaY();
			PointF pf = rotateXY(0, 0, angle, dx, dy);

			dx = pf.x;
			dy = pf.y;

			setX(getX() + dx * scaleFactor);
			setY(getY() + dy * scaleFactor);
		}
	}

	/**
	 * get new partial Bitmap image from currently displed on this view(applied
	 * zooming/moving/rotating)
	 * 
	 * @param frame
	 *            : framing rectangle that you want to cut image from the view
	 *            (as view coordinates)
	 * @return
	 */
	public Bitmap getCurrentImage(Rect frame) {
		Bitmap image = getCurrentImage();
		if ((frame != null) && !frame.isEmpty()) {
			Bitmap tmp = Bitmap.createBitmap(image, frame.left, frame.top, frame.width(),
					frame.height(), null, false);
			image.recycle();
			image = tmp;
		}
		return image;
	}

	/**
	 * get new Bitmap image that currently displed on this view(applied
	 * zooming/moving/rotating).
	 * 
	 * @return
	 */
	public Bitmap getCurrentImage() {
		final Bitmap offscreen = Bitmap.createBitmap(getWidth(), getHeight(),
				Bitmap.Config.ARGB_8888);
		final Canvas canvas = new Canvas(offscreen);
		// modified to support drawables other than BitmapDrawable
		canvas.setMatrix(super.getImageMatrix());
		super.getDrawable().draw(canvas);
		return offscreen;
	}
}
